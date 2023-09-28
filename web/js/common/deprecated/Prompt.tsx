import { unstable_usePrompt } from 'react-router-dom';

/**
 * @deprecated This is a workaround for class components. Prompt is not supported in React Router v6. Use the usePrompt or useBlocker hooks instead.
 * @see https://github.com/remix-run/react-router/issues/8139#issuecomment-1382428200
 */
export function Prompt({ when, message }: { when: boolean; message: string }) {
  unstable_usePrompt({ when, message });
  return null;
}
