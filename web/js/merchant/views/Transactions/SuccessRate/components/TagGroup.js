import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { defaultTagStyle, tagStyles, TAG_MAP, TAG_OVERALL_MAP } from '../constants';

export const Tag = ({ tag, isActive, onSelect, tagStyle }) => {
  const { backgroundColor, borderColor, borderStyle, borderWidth } = tagStyle;

  const onClick = (e) => onSelect(e.target.id);

  return (
    <div
      className="tags-group__tag"
      id={tag.value}
      onClick={onClick}
      style={{
        ...(backgroundColor && isActive && { backgroundColor }),
        ...(borderColor && { borderColor }),
        ...(borderStyle && { borderStyle }),
        ...(borderWidth && { borderWidth }),
      }}
    >
      {tag.name || '--'}
    </div>
  );
};

const TagGroup = ({ isLoading, tags, selectedTags = [], groupBy = '', onSelect }) => {
  if (isLoading) {
    return (
      <div className="tags-loader">
        <PlaceholderLoader />
        <PlaceholderLoader style={{ width: '45%' }} />
      </div>
    );
  }

  if (!tags?.length) return null;

  return (
    <div className="tags-group">
      {tags?.map((tag, idx) => {
        let name = TAG_MAP[tag] ?? tag;
        if (tag === 'Overall') name = TAG_OVERALL_MAP[groupBy];
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
