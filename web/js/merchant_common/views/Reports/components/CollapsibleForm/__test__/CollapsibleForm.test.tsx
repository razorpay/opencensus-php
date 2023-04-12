import React, { useState } from 'react';

import { render, screen, userEvent } from 'test-utils';
import {
  CollapsibleForm,
  CollapsibleFormSection,
  Switch,
  Text,
} from 'merchant_common/views/Reports/components';

describe('CollapsibleForm', () => {
  const App = ({ endComponentVisiblity, ...props }) => {
    const [isSwitchEnabled, setSwitchState] = useState(false);
    return (
      <CollapsibleForm validationsForEachSections={[true]} {...props}>
        <CollapsibleFormSection
          title="Section for testing 1"
          helpText="Just a section 1 for testing purpose"
          endComponent={{
            component: () => <Text>End Component</Text>,
            visible: endComponentVisiblity,
          }}
        >
          <Switch label="Test 1" onChange={setSwitchState} value={isSwitchEnabled} />
        </CollapsibleFormSection>
        <CollapsibleFormSection
          title="Section for testing 2"
          helpText="Just a section 2 for testing purpose"
          disabled
        >
          <Switch label="Test 2" onChange={setSwitchState} value={isSwitchEnabled} />
        </CollapsibleFormSection>
      </CollapsibleForm>
    );
  };

  test('should render component without error', () => {
    render(<App endComponentVisiblity="always" />);
    // section 1
    expect(screen.getByText('Section for testing 1')).toBeInTheDocument();
    expect(screen.getByText('Just a section 1 for testing purpose')).toBeInTheDocument();
    expect(screen.queryByLabelText('Test Switch 1')).not.toBeInTheDocument();
    // section 2
    expect(screen.getByText('Section for testing 2')).toBeInTheDocument();
    expect(screen.getByText('Just a section 2 for testing purpose')).toBeInTheDocument();
    expect(screen.queryByLabelText('Test Switch 2')).not.toBeInTheDocument();
  });

  test('should expand if clicked on a section', async () => {
    render(
      <App
        endComponentVisiblity="on-active"
        validationsForEachSections={[true, false]}
        errorSectionIndex={0}
      />,
    );
    await userEvent.click(screen.getByText('Section for testing 1'));
    expect(screen.queryByLabelText('Test 1 Switch')).toBeInTheDocument();
  });

  test('should not expand if section is disabled', async () => {
    render(<App endComponentVisiblity="on-close" />);
    await userEvent.click(screen.getByText('Section for testing 2'));
    expect(screen.queryByLabelText('Test 2 Switch')).not.toBeInTheDocument();
  });
});
