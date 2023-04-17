import React from 'react';
import defaultIcon from 'assets/reports/default.svg';
import { CardPropsType } from 'merchant_common/views/Reports/features/Overview/types';
import { CardWrapper, Header, Footer, CardLink, CardIcon, TextWrapper } from './style';
import { Heading, Text } from 'merchant_common/views/Reports/components';
import { Link } from 'react-router-dom';
import { availableLinks, reportTypeIconsMap } from './configs';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { trackOverviewSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { DashboardType } from 'merchant_common/views/Reports/types';

export const Card = ({ data, linkBasePath }: CardPropsType): JSX.Element => {
  const { name, description, id, type } = data;
  const { theme } = useTheme();
  const availableLinksArr = availableLinks(id, type);
  const dashboardType = useDashboardType() as DashboardType;
  return (
    <CardWrapper aria-label={`${name} Card`} theme={theme}>
      <div>
        <Header theme={theme}>
          <CardIcon src={reportTypeIconsMap[type] ?? defaultIcon} size="32px" />
          <Heading variant="regular" weight="bold" type="subtle" contrast="low">
            {name}
          </Heading>
        </Header>
        <TextWrapper theme={theme}>
          <Text
            truncateAfterLines={3}
            variant="body"
            type="subdued"
            weight="regular"
            contrast="low"
          >
            {description}
          </Text>
        </TextWrapper>
      </div>
      <Footer count={availableLinksArr.length} theme={theme}>
        {availableLinksArr.map((link) => (
          // eslint-disable-next-line
          //@ts-ignore
          <Link
            key={link.label}
            aria-label={`${link.label} Button`}
            to={`${linkBasePath}/reports/${link.to}`}
            onClick={() =>
              trackOverviewSection({
                actionName: 'Cards Download Link Click',
                properties: {
                  report_type: type,
                  config_id: id,
                },
                dashboardType,
              })
            }
          >
            <Text variant="body" type="normal" weight="bold">
              <CardLink theme={theme}>{link.label}</CardLink>
            </Text>
          </Link>
        ))}
      </Footer>
    </CardWrapper>
  );
};
