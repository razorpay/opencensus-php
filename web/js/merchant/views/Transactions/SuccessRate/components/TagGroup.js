import React, { useCallback } from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { getTagLabelWithOverallTag } from '../helper';
import { defaultTagStyle, DEFAULT_GROUP_BY, tagStyles } from '../constants';
import InfoIcon from './InfoIcon';

export const Tag = ({ tag, isActive, onSelect, tagStyle }) => {
  const { backgroundColor, color, borderStyle, borderWidth } = tagStyle;

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
        <PlaceholderLoader />
        <PlaceholderLoader style={{ width: '45%' }} />
      </div>
    );
  }

  if (!tags?.length) return null;

  return (
    <div className="tag-list">
      {tags?.map((tag, idx) => {
        return (
          <Tag
            key={`${tag}__${idx}`}
            tag={{ name: getTagLabelWithOverallTag({ tag, activeTab, groupBy }), value: tag }}
            onSelect={onSelect}
            isActive={selectedTags.indexOf(tag) > -1}
            tagStyle={tagStyles[idx] ?? defaultTagStyle}
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
