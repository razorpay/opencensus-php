import React, { useEffect } from 'react';
import { History, Location } from 'history';
import { Route } from 'react-router-dom';

import Catalog from './Catalog';

type Match = {
  params: {
    page: string;
  };
};

type POS = {
  location?: typeof Location;
  history?: typeof History;
  match?: Match;
};

const POS = (props: POS): JSX.Element => {
  const { location, history, match } = props;

  useEffect(() => {
    //redirect to catalog if no page mentioned
    const { params } = match ?? {};
    if (!params?.page) {
      history.replace({
        pathname: '/pos/catalog',
      });
    }

    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.pathname]);

  return <Route path="/pos/catalog" element={Catalog} />;
};

export default POS;
