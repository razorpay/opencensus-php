(window as any).webAppUrl =
  localStorage.getItem('webApp_URL') ?? process.env.UNIVERSE_PUBLIC_webApp_REMOTE_ENTRY_PATH;
import('./entryBrowser');
