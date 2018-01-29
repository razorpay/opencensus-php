#!/bin/bash

mkdir -p ../public/dist

# react library to use, based on environment

REACT_ENV=development
if [ "$NODE_ENV" = production ] ; then
  REACT_ENV=production.min
  APPEND_MIN=.min
fi

# admin vendor files
vendor_a=$(cat <<-END
  Promise         promise-polyfill/dist/promise$APPEND_MIN.js
  axios           axios/dist/axios$APPEND_MIN.js
  React           react/umd/react.$REACT_ENV.js
  ReactDOM        react-dom/umd/react-dom.$REACT_ENV.js
  ReactRouterDOM  react-router-dom/umd/react-router-dom$APPEND_MIN.js
  mobx            mobx/lib/mobx.umd.min.js
  mobxReact       mobx-react/index.min.js
  moment          moment/min/moment.min.js
  DayPicker       react-day-picker/lib/daypicker.min.js
  Chart           chart.js/dist/Chart.min.js
  PropTypes  prop-types/prop-types$APPEND_MIN.js
END
)

# merchant vendor files
vendor_m=$(cat <<-END
  Promise         promise-polyfill/dist/promise$APPEND_MIN.js
  axios           axios/dist/axios$APPEND_MIN.js
  React           react/umd/react.$REACT_ENV.js
  ReactDOM        react-dom/umd/react-dom.$REACT_ENV.js
  ReactRouterDOM  react-router-dom/umd/react-router-dom$APPEND_MIN.js
  Redux           redux/dist/redux$APPEND_MIN.js
  ReactRedux      react-redux/dist/react-redux$APPEND_MIN.js
  ReduxForm       redux-form/dist/redux-form$APPEND_MIN.js
  moment          moment/min/moment.min.js
  Chart           chart.js/dist/Chart$APPEND_MIN.js
  PropTypes       prop-types/prop-types$APPEND_MIN.js
  d3              d3/build/d3$APPEND_MIN.js
END
)

even=true
for j in vendor_a vendor_m; do
  for i in ${!j}; do
    if [ $even = true ] ; then
      even=false
    else
      even=true
      cat ./node_modules/$i
      printf '\n'
    fi
  done > ../public/dist/$j.js
  echo ${!j}
done