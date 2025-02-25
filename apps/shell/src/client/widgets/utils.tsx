import {
  LayoutIcon,
  DiscIcon,
  TransactionsIcon,
  SettlementsIcon,
  UserIcon,
  TagIcon,
  ActivityIcon,
  CodeSnippetIcon,
  AppStoreIcon,
  IconComponent,
  SettingsIcon,
} from '@razorpay/blade/components';

export const IconMap: Record<string, IconComponent> = {
  union: DiscIcon,
  transactions: TransactionsIcon,
  settlement: SettlementsIcon,
  activity: ActivityIcon,
  user: UserIcon,
  tag: TagIcon,
  layout: LayoutIcon,
  codeSnippet: CodeSnippetIcon,
  appStore: AppStoreIcon,
  settings: SettingsIcon,
};

export const renderWidget = ({ widget, widgetMapping }) => {
  const widgetComponent = widgetMapping[widget.type];
  if (widgetComponent) {
    return widgetComponent(widget);
  } else {
    return null;
  }
};
