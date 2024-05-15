import RequestBlacklist from 'merchant/views/RiskAndFraud/RiskAnalytics/BlockRule/RequestBlacklist';
import { render, screen, userEvent } from 'test-utils';

const renderApp = (props = {}) => {
  render(<RequestBlacklist isOpen={true} {...props} />);
};

const createDummyFile = () => {
  const blob = new Blob(['upload text file']);
  const file = new File([blob], 'test.csv', {
    type: 'text/plain',
  });
  return file;
};

describe('Tests for RequestBlacklist component (Risk Visibility)', () => {
  test('Should render without breaking', () => {
    renderApp();

    expect(screen.getByText('Request blacklist')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Cancel' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Send request' })).toBeInTheDocument();
  });

  test('Should show all the form fields', () => {
    renderApp();

    expect(screen.getByRole('combobox', { name: 'Parameter required *' })).toBeInTheDocument();
    expect(screen.getByRole('textbox', { name: 'Comments' })).toBeInTheDocument();
    expect(screen.getByRole('textbox', { name: 'Email updates to' })).toBeInTheDocument();
    expect(screen.getByText(/Upload XLS or XLSV of list items/)).toBeInTheDocument();
  });

  test('Should show error if send request is clicked without filling all required details', async () => {
    renderApp();

    const sendRequestButton = screen.getByRole('button', { name: 'Send request' });
    await userEvent.click(sendRequestButton);

    await expect(screen.getAllByText('This field is required')).toHaveLength(2);
  });

  test('Should call api with correct parameters if all required fields are filled', async () => {
    renderApp();

    //parameters
    await userEvent.click(screen.getAllByRole('combobox')[0]);
    await userEvent.click(screen.getByRole('option', { name: 'Contact' }));

    //email
    await userEvent.type(
      screen.getByRole('textbox', { name: 'Email updates to' }),
      'sanchit@gmail.com',
    );

    //file
    const fileInput = screen.getByTestId('file-input');
    await userEvent.click(screen.getByText(/Upload XLS or XLSV of list items/));
    await userEvent.upload(fileInput, createDummyFile());

    const sendRequestButton = screen.getByRole('button', { name: 'Send request' });
    await userEvent.click(sendRequestButton);
  });

  test('Should call onDismiss if cancel is clicked', async () => {
    const onDismiss = jest.fn();
    renderApp({ onDismiss });

    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    await userEvent.click(cancelButton);

    await expect(onDismiss).toHaveBeenCalled();
  });
});
