import React, { useCallback } from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { getUser } from 'merchant/store';
import { getTagLabel } from '../helper';
import { defaultTagStyle, tagStyles, TAG_OVERALL_MAP } from '../constants';

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
        let name = getTagLabel(tag);
        if ((!getUser()?.isOptimizerEnabled || activeTab === 'Overall') && tag === 'Overall') {
          name = TAG_OVERALL_MAP[groupBy];
        }
        return (
          <Tag
            key={`${tag}__${idx}`}
            tag={{ name, value: tag }}
            onSelect={onSelect}
            isActive={selectedTags.indexOf(tag) > -1}
            tagStyle={tagStyles[idx] ?? defaultTagStyle}
          />
        );
      })}
    </div>
  );
};

export default TagGroup;
