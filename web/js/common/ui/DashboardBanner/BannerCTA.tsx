import React from 'react';
import { Link } from 'react-router-dom';
import { getCTAClassName } from './util';
import { CTA } from './TypesDeclare/DashboardBannerTypes';

const BannerCTA = ({ clickHandler, url, label, isExternal, type }: CTA): React.ReactElement => {
  let urlPath: any = url?.length ? url : undefined;

  if (isExternal) {
    return (
      <a
        className={getCTAClassName(type)}
        onClick={clickHandler}
        href={urlPath}
        target="_blank"
        rel="noreferrer noopener"
      >
        {label}
      </a>
    );
  } else {
    urlPath = {
      pathname: url,
    };

    return (
      <Link to={urlPath} className={getCTAClassName(type)} onClick={clickHandler}>
        {label}
      </Link>
    );
  }
};

export default BannerCTA;
