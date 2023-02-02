import React from 'react';
import { withRouter } from 'react-router-dom';
import { Heading, Link } from '@razorpay/blade/components';
import { CardComponent, CardHeader, ProductIcon, CardItems } from './styled';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import {
  SectionCardPropsInterface,
  SubSection,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { Modules } from 'common/constant/enums';

const SectionCard = ({
  title,
  icon,
  subSections,
  iconBackground,
  isMobile,
  history,
}: SectionCardPropsInterface): JSX.Element => {
  const onNavLinkClick = ({ href, title: linkTitle }: SubSection) => {
    history.push(href);
    analyticsTrackWithUserInfo({
      objectName: 'Business Profile',
      actionName: 'Clicked',
      screen: Modules.AccountAndSettings,
      properties: {
        clickedElement: title,
        section: linkTitle,
      },
    });
  };
  return (
    <CardComponent>
      <CardHeader>
        <ProductIcon iconBackground={iconBackground}>
          <i className={`i ${icon}`} />
        </ProductIcon>
        <Heading size="small">{title}</Heading>
      </CardHeader>
      {!isMobile && <Divider noMargin />}
      <CardItems>
        {subSections.map((each) => {
          return (
            <Link key={each.id} variant="button" onClick={onNavLinkClick.bind(null, each)}>
              {each.title}
            </Link>
          );
        })}
      </CardItems>
    </CardComponent>
  );
};

export default withRouter(SectionCard);
