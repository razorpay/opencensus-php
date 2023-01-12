import React from 'react';
import { StyledBreadcrumb, StyledBreadcrumbItem } from 'common/components/Breadcrumb/Styled';

interface BreadcrumbItem {
  label: string;
  link: string;
}

interface BreadcrumbProps {
  items: BreadcrumbItem[];
}

const Breadcrumb = ({ items }: BreadcrumbProps): JSX.Element => {
  return (
    <StyledBreadcrumb className="breadcrumb-container" data-testid="breadcrumb">
      <StyledBreadcrumbItem to={items[0]?.link}>
        <i className="i i-arrow-back" />
      </StyledBreadcrumbItem>
      {items.map(({ label, link }, index) => {
        const chevronRight =
          index === items.length - 1 ? null : <i className="i i-chevron-right" />;
        return (
          <StyledBreadcrumbItem key={index} to={link} className="breadcrumb__item">
            {label} {chevronRight}
          </StyledBreadcrumbItem>
        );
      })}
    </StyledBreadcrumb>
  );
};

export default Breadcrumb;
