import { act, create, type ReactTestRenderer } from 'react-test-renderer';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { AuthProvider, useAuth } from './AuthContext';

const auth = vi.hoisted(() => ({ getSession: vi.fn(), onAuthStateChange: vi.fn(), unsubscribe: vi.fn() }));
vi.mock('../supabase/client', () => ({ supabase: { auth } }));

let renderer: ReactTestRenderer | undefined;
function Status() {
  const { loading, user } = useAuth();
  return <span>{loading ? 'loading' : user ? user.id : 'visitor'}</span>;
}
async function mount() {
  await act(async () => { renderer = create(<AuthProvider><Status /></AuthProvider>); });
}
const status = () => renderer!.root.findByType('span').children[0];

beforeEach(() => {
  vi.resetAllMocks();
  auth.onAuthStateChange.mockReturnValue({ data: { subscription: { unsubscribe: auth.unsubscribe } } });
});
afterEach(() => { act(() => renderer?.unmount()); renderer = undefined; });

describe('AuthProvider startup', () => {
  it('loads an existing session', async () => {
    auth.getSession.mockResolvedValue({ data: { session: { user: { id: 'chef' } } } });
    await mount();
    expect(status()).toBe('chef');
  });
  it('releases the loading screen after a session lookup failure', async () => {
    auth.getSession.mockRejectedValue(new Error('Session storage unavailable'));
    await mount();
    expect(status()).toBe('visitor');
  });
  it('handles auth events while the initial lookup is pending', async () => {
    auth.getSession.mockReturnValue(new Promise(() => {}));
    await mount();
    expect(status()).toBe('loading');
    act(() => auth.onAuthStateChange.mock.calls[0][0]('SIGNED_IN', { user: { id: 'chef' } }));
    expect(status()).toBe('chef');
  });
  it('unsubscribes and handles a late rejection after unmount', async () => {
    let reject!: (error: Error) => void;
    auth.getSession.mockReturnValue(new Promise((_, fail) => { reject = fail; }));
    await mount();
    act(() => renderer!.unmount());
    renderer = undefined;
    await act(async () => { reject(new Error('Late failure')); });
    expect(auth.unsubscribe).toHaveBeenCalledOnce();
  });
});
