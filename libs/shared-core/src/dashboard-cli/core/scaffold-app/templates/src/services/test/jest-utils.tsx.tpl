import { waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// re-export everything
export * from '@testing-library/react';

// override render method
export { waitFor, userEvent };
