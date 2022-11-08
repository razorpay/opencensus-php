import React, { ChangeEvent } from 'react';

// components
import Spinner from 'common/ui/Spinner';
import Button from 'common/new-ui/Button';
import Accordion, {
  AccordionItem,
  AccordionItemTitle,
  AccordionItemContent,
} from 'common/ui/Accordion';

// helpers
import { useHSCodeSearch } from 'merchant/views/Account/Profile/components/FIRC/HSCode/helpers';

// prop types
type ItemType = { label: string; value: string; description: string };

interface HSCodeSearchProps {
  isLoading?: boolean;
  codeList: { label: string; codes: ItemType[] }[];
  selected?: string;
  onSelect?: (code: ItemType) => void;
  onNext?: () => void;
}

const HSCodeSearch = ({ isLoading, codeList, selected, onSelect, onNext }: HSCodeSearchProps) => {
  const { search, items, onClear, onSearch } = useHSCodeSearch(codeList);

  const handleOnChange = (evt: ChangeEvent<HTMLInputElement>) => onSearch(evt.target.value);

  const handleOnSelect = (code: ItemType) => {
    if (typeof onSelect === 'function') {
      onSelect(code);
    }
  };

  if (isLoading) {
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="purpose-code-container hs-code-container">
      <div className="header-section content-box">
        <div className="search-container">
          <i className="i i-search" />
          <input
            type="text"
            placeholder="Search code or Industry"
            name="search_code"
            className="form-control purpose-code-search-input"
            autoFocus={true}
            value={search}
            onChange={handleOnChange}
          />
          <Button.Transparent className="close-icon" onClick={onClear}>
            <i className="i i-close" />
          </Button.Transparent>
        </div>
      </div>

      <div className="scroll-container">
        {search &&
          (items?.length > 0 ? (
            <div className="list-container">
              {items.map((item) => (
                <div key={item.value} className="list-item" onClick={() => handleOnSelect(item)}>
                  <p>{item.label}</p>
                  <div className="pr-10">{item.description}</div>
                  {selected === item.value && <i className="i i-tick tick-icon" />}
                </div>
              ))}
            </div>
          ) : (
            <p className="content-box no-content">
              No results found for &quot;<b>{search}</b>&quot;
            </p>
          ))}

        <div className="collapse-section">
          {codeList?.length > 0 && (
            <Accordion>
              {codeList.map(({ label, codes }) => (
                <AccordionItem key={label}>
                  <AccordionItemTitle>{label}</AccordionItemTitle>
                  <AccordionItemContent>
                    <div className="list-container">
                      {codes.map((code) => (
                        <div
                          key={code.value}
                          className="list-item"
                          onClick={() => handleOnSelect(code)}
                        >
                          <p className="fw-600">{code.value}</p>
                          <div className="pr-10">{code.description}</div>
                          {selected === code.value && <i className="i i-tick tick-icon" />}
                        </div>
                      ))}
                    </div>
                  </AccordionItemContent>
                </AccordionItem>
              ))}
            </Accordion>
          )}
        </div>
      </div>

      <div className="footer-section">
        <Button.Primary className="btn-block" disabled={!selected} onClick={onNext}>
          Next
        </Button.Primary>
      </div>
    </div>
  );
};

export default React.memo(HSCodeSearch);
