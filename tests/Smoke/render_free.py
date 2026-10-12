"""Exercise the deployed image, including Unix identity, writes and restarts.

Run: python3 tests/Smoke/render_free.py portal-receitas-free:ci
Only the temporary container created by this test is modified and removed.
"""

import http.cookiejar
import json
import re
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid


def docker(*args):
    return subprocess.check_output(["docker", *args], text=True).strip()


container = "portal-smoke-" + uuid.uuid4().hex[:12]
checks = 0

try:
    docker(
        "run", "-d", "--name", container, "--memory", "512m",
        "-p", "127.0.0.1::10000", "-e", "PORT=10000",
        # An old Render setting must not restore the broken root connection.
        "-e", "DB_USER=root", sys.argv[1],
    )
    address = docker("port", container, "10000/tcp")
    base = "http://" + address
    client = urllib.request.build_opener(
        urllib.request.ProxyHandler({}),
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),
    )

    def request(path, data=None, method=None, form=False):
        body = (urllib.parse.urlencode(data).encode() if form else json.dumps(data).encode()) if data is not None else None
        req = urllib.request.Request(
            base + path, data=body, method=method,
            headers={"Content-Type": "application/x-www-form-urlencoded" if form else "application/json"} if body else {},
        )
        try:
            response = client.open(req, timeout=5)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.read().decode()

    def expect(path, status=200, text=None, data=None, method=None, form=False):
        global checks
        actual, body = request(path, data, method, form)
        if actual != status or (text is not None and text not in body):
            raise AssertionError(f"{path}: expected {status}/{text!r}, got {actual}: {body[:400]}")
        checks += 1
        if path.startswith('/api/'):
            json.loads(body)  # PHP warnings must never corrupt the JSON response.
        return body

    def wait_ready():
        deadline = time.monotonic() + 90
        while time.monotonic() < deadline:
            try:
                if request("/readyz.php") == (200, "ok"):
                    return
            except (OSError, urllib.error.URLError):
                pass
            time.sleep(1)
        raise AssertionError("Catalog did not become ready within 90 seconds")

    def restart():
        global base
        docker("restart", "-t", "10", container)
        # Docker may allocate a new ephemeral host port after restarting.
        base = "http://" + docker("port", container, "10000/tcp")
        wait_ready()

    wait_ready()
    expect("/readyz.php", text="ok")
    expect("/healthz.php", text="ok")
    expect("/", text="Macarrão à carbonara")
    expect("/?pesquisa=carbonara", text="Macarrão à carbonara")
    expect("/receita/1/macarrao-a-carbonara", text="Macarrão à carbonara")
    expect("/sitemap.xml", text="/receita/1/macarrao-a-carbonara")
    expect("/assets/css/base.css", text="box-sizing: border-box")
    expect("/api/me.php", status=401)

    email = "smoke@example.com"
    password = "SmokeTest123"
    expect("/api/register.php", status=201, data={
        "nome": "Smoke Test", "email": email, "senha": password,
        "categoria": 2, "aceite": True,
    })
    expect("/api/login.php", data={"email": email, "senha": password})
    expect("/api/me.php", text=email)
    page = expect("/receita/1/macarrao-a-carbonara")
    token = re.search(r'data-csrf="([^"]+)"', page)
    if token is None:
        raise AssertionError("Recipe did not expose its CSRF token")
    payload = {"idReceita": 1, "_csrf": token.group(1)}
    expect("/api/favorites.php", status=403, data={"idReceita": 1})
    for path in ("/api/favorites.php", "/api/ratings.php", "/api/comments.php"):
        expect(path, status=404, data={**payload, "idReceita": 999999, "nota": 5, "texto": "No recipe"})
    expect("/api/comments.php", status=400, data={**payload, "texto": ["invalid", "array"]})
    expect("/api/favorites.php", status=403, data={**payload, "_csrf": ["invalid"]})
    expect("/profile.php", data={"salvar": "1", "_csrf": ["invalid"]})
    expect("/profile.php", form=True, data={"salvar": "1", "_csrf[]": "invalid", "nome[]": "invalid"})
    expect("/profile.php", form=True, data={"salvar": "1", "_csrf": token.group(1), "nome[]": "invalid"})
    # A successful profile submission ends the session; authenticate again.
    expect("/api/login.php", data={"email": email, "senha": password})
    page = expect("/receita/1/macarrao-a-carbonara")
    payload["_csrf"] = re.search(r'data-csrf="([^"]+)"', page).group(1)
    expect("/api/favorites.php", text='"favorited":true', data=payload)
    expect("/api/favorites.php", text='"favorited":false', data=payload)
    expect("/api/ratings.php", data={**payload, "nota": 5})
    expect("/api/ratings.php", data={**payload, "nota": 4})
    expect("/api/comments.php", status=201, data={**payload, "texto": "Smoke test comment"})

    # Existing data must survive restarting the same container; no seed reset.
    restart()
    expect("/api/login.php", data={"email": email, "senha": password})
    expect("/receita/1/macarrao-a-carbonara", text="Smoke test comment")

    # A running Apache alone must not make the production readiness check pass.
    docker("exec", container, "mariadb-admin", "--protocol=socket", "-u", "root", "shutdown")
    expect("/readyz.php", status=503, text="unavailable")
    expect("/healthz.php", text="ok")
    expect("/", status=503, text="temporariamente")
    restart()
    expect("/", text="Macarrão à carbonara")
    print(f"PASS: {checks} functional checks (512 MiB, writes, restart, outage and recovery)")
except BaseException:
    subprocess.run(["docker", "logs", "--tail", "100", container], check=False)
    raise
finally:
    subprocess.run(["docker", "rm", "-f", container], check=False, stdout=subprocess.DEVNULL)
