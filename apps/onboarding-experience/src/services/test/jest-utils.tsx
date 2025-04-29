import { waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// @ts-ignore
import { renderHook } from '@testing-library/react-hooks';

// re-export everything
export * from '@testing-library/react';

// override render method
export { waitFor, userEvent, renderHook };
