import { ExternalLinkIcon, Link, Text } from '@razorpay/blade/components';
import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { Content, ListItem, Order } from './Styled';

const WebsiteDetailsSections = ({ history, closeModal, websiteInfo: { websitesData = [] } }) => {
  const handleClick = () => {
    history.push('/website-app-settings/business-website-details');
    closeModal();
  };

  if (!websitesData.length) return null;

  return (
    <div className="Input">
      <div className="Input-label">Website{websitesData.length > 1 ? '(s)' : ''}</div>
      <Content className="Input-content">
        <Content>
          {websitesData.map((each, index) => (
            <ListItem key={`details-${index}`}>
              <Order />
              <Text color="surface.text.gray.muted">{each}</Text>
            </ListItem>
          ))}
        </Content>
        <Text size="small" color="surface.text.gray.muted">
          You’ll be able to collect international card payments only on registered website(s). To
          register another website, use the link below:
        </Text>
        <Link
          onClick={handleClick}
          variant="button"
          icon={ExternalLinkIcon}
          iconPosition="right"
          size="small"
        >
          Add/Update website
        </Link>
      </Content>
    </div>
  );
};

export default withRouter(WebsiteDetailsSections);
