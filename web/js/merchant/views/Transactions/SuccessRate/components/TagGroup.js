import React, { useCallback } from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { getTagLabelWithOverallTag } from 'merchant/views/Transactions/SuccessRate/helper';
import { DEFAULT_GROUP_BY } from 'merchant/views/Transactions/SuccessRate/constants';
import InfoIcon from './InfoIcon';

export const Tag = ({ tag, isActive, onSelect }) => {
  const { backgroundColor, color, borderStyle, borderWidth } = tag;

  const onCheck = useCallback((e) => onSelect(e.target.value), [onSelect]);

  return (
    <label className="tag-list__item">
      <input
        type="checkbox"
        checked={isActive}
        name={tag.name}
        value={tag.value}
        onChange={onCheck}
      />
      <span
        className="tag-list__item-label"
        style={{
          ...(backgroundColor && isActive && { backgroundColor }),
          ...(color && { color }),
          ...(color && { borderColor: color }),
          ...(borderStyle && { borderStyle }),
          ...(borderWidth && { borderWidth }),
        }}
      >
        <i className="i i-tick tick tick--check" />
        {tag.name || '--'}
      </span>
    </label>
  );
};

const TagGroup = ({ isLoading, tags, selectedTags = [], groupBy = '', onSelect, activeTab }) => {
  if (isLoading) {
    return (
      <div className="tag-list">
        <PlaceholderLoader data-testid="sr-dashboard-chart-shimmer" />
        <PlaceholderLoader data-testid="sr-dashboard-chart-shimmer" style={{ width: '45%' }} />
      </div>
    );
  }

  if (!tags?.length) return null;

  return (
    <div className="tag-list">
      {tags?.map((tag, idx) => {
        const { name } = tag;
        const isActive = selectedTags.findIndex((selectedTag) => selectedTag.name === name) > -1;

        return (
          <Tag
            key={`${name}__${idx}`}
            isActive={isActive}
            onSelect={onSelect}
            tag={{
              ...tag,
              name: getTagLabelWithOverallTag({ tag: name, activeTab, groupBy }),
              value: name,
            }}
          />
        );
      })}

      {groupBy === DEFAULT_GROUP_BY.UPI && (
        <InfoIcon text="Intent is when the customer has chosen from a list of UPI apps installed on their phone to make the payment . Collect is when the customer has directly added their UPI ID/VPA details to make the payment." />
      )}
    </div>
  );
};

export default TagGroup;
