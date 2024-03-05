import { Page } from 'apps/self-serve/src/App/Transactions/v2/common/types';

export interface Config {
  image: { src: string; alt: string };
  title: string;
  subtitle: string;
  link?: { href: string; linkText: string };
}

export interface NoSearchResultProps {
  page: Page;
}

export interface NoSearchResultTemplateProps {
  page: Page;
  config: (page: Page) => Config;
}
