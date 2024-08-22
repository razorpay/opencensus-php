(window as any).shellUrl =
  localStorage.getItem('SHELL_URL') ?? process.env.UNIVERSE_PUBLIC_SHELL_REMOTE_ENTRY_PATH;
import('./entryBrowser');
