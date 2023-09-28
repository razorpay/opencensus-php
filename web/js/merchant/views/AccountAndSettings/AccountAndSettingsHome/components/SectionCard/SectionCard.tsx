import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { Heading, Link, Badge, OffersIcon } from '@razorpay/blade/components';
import { CardComponent, CardHeader, ProductIcon, CardItems, SubSectionItem } from './styled';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import {
  SectionCardPropsInterface,
  SubSection,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { Modules } from 'common/constant/enums';
import type { WithRouterProps } from 'common/deprecated/RouteComponentProps';

const SectionCard = ({
  title,
  icon,
  subSections,
  iconBackground,
  isMobile,
  history,
}: SectionCardPropsInterface & WithRouterProps): JSX.Element => {
  const onNavLinkClick = ({ href, title: linkTitle, onLinkClick }: SubSection) => {
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
    if (onLinkClick) onLinkClick();
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
            <SubSectionItem key={each.id}>
              <Link variant="button" onClick={onNavLinkClick.bind(null, each)}>
                {each.title}
              </Link>
              {each.isNew && (
                <Badge variant="positive" fontWeight="bold" icon={OffersIcon}>
                  NEW
                </Badge>
              )}
            </SubSectionItem>
          );
        })}
      </CardItems>
    </CardComponent>
  );
};

export default withRouter<any>(SectionCard);
