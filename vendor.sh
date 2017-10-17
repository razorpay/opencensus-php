mkdir -p public/dist

# react library to use, based on environment

REACT_ENV=development
if [ "$NODE_ENV" = production ] ; then
  REACT_ENV=production.min
fi

files=$(cat <<-END
  axios           axios/dist/axios.min.js
  React           react/umd/react.$REACT_ENV.js
  ReactDOM        react-dom/umd/react-dom.$REACT_ENV.js
  ReactRouterDOM  react-router-dom/umd/react-router-dom.min.js
  mobx            mobx/lib/mobx.umd.min.js
  mobxReact       mobx-react/index.min.js
  Chartist        chartist/dist/chartist.min.js
END
)

even=true
for i in $files; do
  if [ $even = true ] ; then
    even=false
  else
    even=true
    cat node_modules/$i
    echo $'\n'
  fi
done > public/dist/vendor.js
echo $files
