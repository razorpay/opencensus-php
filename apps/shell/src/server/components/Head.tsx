import React, { ReactElement } from 'react';
import { Helmet } from 'react-helmet-async';

interface Props {
  title?: string;
  titleTemplate?: string;
  description?: string;
  children?: ReactElement | ReactElement[];
  ogImage?: string;
  author?: string;
}

const DEFAULT_TITLE = 'Universe';
const DEFAULT_DESCRIPTION = 'Powered by Universe';
const DEFAULT_OG_IMAGE = '';
const DEFAULT_AUTHOR = 'Razorpay';

const Head = ({
  title,
  titleTemplate = `%s | ${DEFAULT_TITLE}`,
  description = DEFAULT_DESCRIPTION,
  children,
  ogImage = DEFAULT_OG_IMAGE,
  author = DEFAULT_AUTHOR,
}: Props): JSX.Element => {
  const ogTitle = title ?? DEFAULT_TITLE;

  return (
    <Helmet
      titleTemplate={titleTemplate}
      defaultTitle={DEFAULT_TITLE}
      title={title}
      meta={[
        {
          property: 'og:title',
          content: ogTitle,
        },
        {
          name: 'twitter:title',
          content: ogTitle,
        },
        {
          name: 'description',
          content: description,
        },
        {
          property: 'og:description',
          content: description,
        },
        {
          name: 'twitter:description',
          content: description,
        },
        {
          property: 'og:type',
          content: 'website',
        },
        {
          property: 'og:image',
          content: ogImage,
        },
        {
          property: 'twitter:image',
          content: ogImage,
        },
        {
          name: 'twitter:card',
          content: 'summary_large_image',
        },
        {
          name: 'twitter:creator',
          content: author,
        },
      ]}
    >
      {children}
    </Helmet>
  );
};

export default Head;
