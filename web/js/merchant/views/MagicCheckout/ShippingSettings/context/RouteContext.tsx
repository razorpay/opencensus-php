import React from 'react';

export type ShippingEngineRoute = 'preview' | 'profile';

interface RouteContext {
  activeRoute: ShippingEngineRoute;
  setActiveRoute: (route: ShippingEngineRoute) => void;
}

const ShippingSettingsRouteContext = React.createContext<RouteContext | null>(null);

const ShippingSettingsRouteContextProvider = ({
  children,
}: {
  children: React.ReactNode;
}): JSX.Element => {
  const [activeRoute, setActiveRoute] = React.useState<ShippingEngineRoute>('preview');
  return (
    <ShippingSettingsRouteContext.Provider value={{ activeRoute, setActiveRoute }}>
      {children}
    </ShippingSettingsRouteContext.Provider>
  );
};

const useShippingSettingsRouteContext = (): RouteContext => {
  const ctx = React.useContext(ShippingSettingsRouteContext);
  if (!ctx) {
    throw Error(
      'useShippingSettingsRouteContext cannot be used inside components wrapped with RouteContextProvider',
    );
  }
  return ctx;
};

export { ShippingSettingsRouteContextProvider, useShippingSettingsRouteContext };
