import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { defaultTagStyle, tagStyles } from '../constants';

export const Tag = ({ tag, isActive, onSelect, tagStyle }) => {
  const { backgroundColor, borderColor, borderStyle, borderWidth } = tagStyle;

  const onClick = (e) => onSelect(e.target.id);

  return (
    <div
      className="tags-group__tag"
      id={tag}
      onClick={onClick}
      style={{
        ...(backgroundColor && isActive && { backgroundColor }),
        ...(borderColor && { borderColor }),
        ...(borderStyle && { borderStyle }),
        ...(borderWidth && { borderWidth }),
      }}
    >
      {tag}
    </div>
  );
};

const TagGroup = ({ isLoading, tags, selectedTags = [], onSelect }) => {
  if (isLoading) return <PlaceholderLoader style={{ width: '45%' }} />;
  if (!tags?.length) return null;

  return (
    <div className="tags-group">
      {tags?.map((tag, idx) => {
        return (
          <Tag
            key={`${tag}__${idx}`}
            tag={tag}
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
