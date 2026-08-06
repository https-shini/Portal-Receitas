import * as Sentry from '@sentry/react';

/** Inicia o Sentry só se houver DSN configurado (§6.17). No-op em dev sem DSN. */
export function initMonitoring() {
  const dsn = import.meta.env.VITE_SENTRY_DSN;
  if (!dsn) return;
  Sentry.init({
    dsn,
    environment: import.meta.env.MODE,
    tracesSampleRate: 0.1,
    integrations: [Sentry.browserTracingIntegration()],
  });
}
