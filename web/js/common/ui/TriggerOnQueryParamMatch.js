import React, { useEffect } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import qs from 'query-string';
import { get, isFunction } from 'lodash';

/**
 * Component to parse query params and calls corresponding function on match
 *
 * @param {({ key: string, value: string, trigger: function }[])} queryParamsMapping - Calls trigger function if key and value are in query params
 * @param {React.Component|null} children - Component
 * @return {React.Component|null} children
 * @example <TriggerOnQueryParamMatch queryParamMapping={[{ key: "queryParamKey", value: "queryParamValue", trigger: onQueryParamMatchFunc ]} />
 * @example <TriggerOnQueryParamMatch queryParamMapping={[{ key: "queryParamKey", value: "queryParamValue", trigger: onQueryParamMatchFunc ]}><ChildComponent /><TriggerOnQueryParamMatch />
 */
function TriggerOnQueryParamMatch({ children = null, location, queryParamsMapping }) {
  useEffect(() => {
    if (!location.search) return;

    const queryParams = qs.parse(location.search);

    queryParamsMapping.some((mapping) => {
      if (get(queryParams, mapping.key) === mapping.value && isFunction(mapping.trigger)) {
        mapping.trigger();
        return true;
      }
      return false;
    });
  }, []);

  return children;
}

export default withRouter(TriggerOnQueryParamMatch);
