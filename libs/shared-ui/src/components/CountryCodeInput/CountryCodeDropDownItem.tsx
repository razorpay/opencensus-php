import React, { useCallback } from 'react';
import { DropDownItem, LeftIconContainer } from './styled';
import { CountryCodeDropDownItemProps } from './types';

const CountryCodeDropDownItem = ({
  countryData,
  onSelect,
}: CountryCodeDropDownItemProps): JSX.Element => {
  const onItemSelect = useCallback(
    (e: React.MouseEvent<HTMLDivElement, MouseEvent>) => {
      e.stopPropagation();
      onSelect(countryData);
    },
    [onSelect, countryData],
  );

  return (
    <DropDownItem key={countryData.country} onClick={onItemSelect}>
      <LeftIconContainer>
        <span className={`flag ${countryData.country}`} />
      </LeftIconContainer>
      <span dangerouslySetInnerHTML={{ __html: countryData.label }} />
    </DropDownItem>
  );
};

export default CountryCodeDropDownItem;
