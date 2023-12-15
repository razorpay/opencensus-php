export const delay = (time = 1000): Promise<void> => new Promise((r) => setTimeout(r, time));
