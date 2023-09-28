import { useEffect, ReactNode } from 'react';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';

type Obj = Record<string, unknown>;

type AppPropType = {
  size: string;
  component: ReactNode;
};

type AppProps = {
  openModal?: ({ size, component }: AppPropType) => Obj;
  component: ReactNode;
};

function App(props: AppProps): null {
  useEffect(() => {
    props.openModal?.({
      size: 'medium',
      component: props.component,
    });
  }, [props.component, props.openModal]);

  return null;
}

export default connect(null, { ...ModalActions })(App);
