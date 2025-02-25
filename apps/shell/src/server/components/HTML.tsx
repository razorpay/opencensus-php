import React, { ReactElement, JSXElementConstructor } from 'react';
import { HelmetContext } from 'react-helmet-async';

type LoadableTags = ReactElement<
  Record<string, unknown>,
  string | JSXElementConstructor<unknown>
>[];

interface Props {
  children?: string;
  scriptTags: LoadableTags;
  linkTags: LoadableTags;
  styleTags: LoadableTags;
  helmetContext?: HelmetContext;
  isOldFlow?: boolean;
}

const HTML = ({
  children,
  scriptTags,
  linkTags,
  styleTags,
  helmetContext: { helmet } = {},
  isOldFlow,
}: Props): JSX.Element => (
  <html lang="en">
    <head>
      {helmet ? (
        <>
          {helmet.base.toComponent()}
          {helmet.meta.toComponent()}
          {helmet.link.toComponent()}
          {helmet.script.toComponent()}
        </>
      ) : null}
      {linkTags}
      {styleTags}
    </head>
    <body>
      {!isOldFlow && children ? (
        <div
          id="root"
          dangerouslySetInnerHTML={{
            __html: children,
          }}
        />
      ) : null}
      {isOldFlow ? (
        <>
          <div id="react-root" className="react-root"></div>
          <div id="splash"></div>
          <div className="app" id="app"></div>
        </>
      ) : null}

      {scriptTags}
    </body>
  </html>
);

export default HTML;
