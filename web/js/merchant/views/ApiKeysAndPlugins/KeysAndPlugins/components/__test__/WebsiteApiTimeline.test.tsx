import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from 'test-utils';
import { WebsiteApiTimeline, WebsiteApiModeSwitchFooter } from '../WebsiteApiTimeline';
import { Status as WebsiteStatusEnum } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils';

describe('Generate Key WebsiteApiTimeline', () => {
  const handleNavigateToWebsite = jest.fn();

  it('renders success state', () => {
    render(
      <WebsiteApiTimeline
        status={WebsiteStatusEnum.Success}
        handleNavigateToWebsite={handleNavigateToWebsite}
      />,
    );
    expect(screen.getByText(/Website review completed/i)).toBeInTheDocument();
  });

  it('renders BvsInProgress state', () => {
    render(
      <WebsiteApiTimeline
        status={WebsiteStatusEnum.BvsInProgress}
        handleNavigateToWebsite={handleNavigateToWebsite}
      />,
    );
    expect(
      screen.getByText(/Website in review. Expect an update within 10mins/i),
    ).toBeInTheDocument();
  });

  it('renders WorkflowInReview state', () => {
    render(
      <WebsiteApiTimeline
        status={WebsiteStatusEnum.WorkflowInReview}
        handleNavigateToWebsite={handleNavigateToWebsite}
      />,
    );
    expect(screen.getByText(/Website in review. Expect an update by/i)).toBeInTheDocument();
  });

  it('renders BvsNeedsClarification state and triggers navigation', () => {
    render(
      <WebsiteApiTimeline
        status={WebsiteStatusEnum.BvsNeedsClarification}
        handleNavigateToWebsite={handleNavigateToWebsite}
      />,
    );
    fireEvent.click(screen.getByText(/Take Action/i));
    expect(handleNavigateToWebsite).toHaveBeenCalledWith({ bvsModalVisible: 'true' });
  });

  it('renders WorkflowNeedsClarification state and triggers navigation', () => {
    render(
      <WebsiteApiTimeline
        status={WebsiteStatusEnum.WorkflowNeedsClarification}
        handleNavigateToWebsite={handleNavigateToWebsite}
      />,
    );
    fireEvent.click(screen.getByText(/Resolve now/i));
    expect(handleNavigateToWebsite).toHaveBeenCalledWith({ clarificationModalVisible: 'true' });
  });

  it('renders default state and triggers navigation', () => {
    render(<WebsiteApiTimeline status={null} handleNavigateToWebsite={handleNavigateToWebsite} />);
    fireEvent.click(screen.getByText(/Add Website/i));
    expect(handleNavigateToWebsite).toHaveBeenCalled();
  });
});

describe('Generate Key WebsiteApiModeSwitchFooter', () => {
  const switchMode = jest.fn();

  it('renders and triggers switch mode', () => {
    render(<WebsiteApiModeSwitchFooter switchMode={switchMode} />);
    fireEvent.click(screen.getByText(/Turn on Test mode/i));
    expect(switchMode).toHaveBeenCalledWith('test');
  });
});
