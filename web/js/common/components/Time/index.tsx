import React, { useEffect, useState } from 'react';
import moment from 'moment';

const getUpdateInterval = (date) => {
  const diff = moment().diff(date);

  if (diff < 60 * 60 * 1000) {
    return 60 * 1000;
  }
  return 24 * 60 * 60;
};

interface TimePropsT {
  value: string | number;
  relative: boolean;
  format?: string;
}
const Time: React.FC<TimePropsT> = ({ value, format = 'DD MMM YYYY', relative }) => {
  const initialDateValue = typeof value === 'string' ? moment(value) : moment.unix(value);
  const [date, setDate] = useState(initialDateValue);
  const [displayText, setDisplayText] = useState('');
  const isoString = date.toISOString();
  const title = `${date.toDate()}`;

  useEffect(() => {
    const updatedDateValue = typeof value === 'string' ? moment(value) : moment.unix(value);
    const text = relative ? updatedDateValue.fromNow() : updatedDateValue.format(format);
    setDate(updatedDateValue);
    setDisplayText(text);
  }, [value, relative]);

  useEffect(() => {
    if (!relative) {
      return undefined;
    }
    const timer = window.setInterval(() => {
      setDisplayText(date.fromNow());
    }, getUpdateInterval(date));
    return () => {
      window.clearInterval(timer);
    };
  }, []);

  if (!value) {
    return <span> -- </span>;
  }

  return (
    <time dateTime={`${isoString}`} title={`${title}`}>
      {displayText}
    </time>
  );
};

export default Time;
