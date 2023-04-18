import React from 'react';
import TimelineItem from './TimelineItem';

type TimelineTypes = {
  data: {
    icon?: JSX.Element;
    content?: JSX.Element | string;
  }[];
};

const Timeline = ({ data }: TimelineTypes): JSX.Element => {
  return (
    <div aria-label="timeline">
      {data.map(({ icon, content }, index) => (
        <TimelineItem
          key={index}
          icon={icon}
          content={content}
          isConnectorRequired={data.length - 1 !== index}
        />
      ))}
    </div>
  );
};

export default Timeline;
