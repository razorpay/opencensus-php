let mode: ModeT = 'test';
export type ModeT = 'test' | 'live';

export function getMode(): string {
  return mode;
}

export function setMode(value: ModeT): ModeT {
  mode = value;
  return mode;
}
