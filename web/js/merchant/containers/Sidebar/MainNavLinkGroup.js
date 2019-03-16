import ShowWhen from 'merchant/components/ShowWhen';
import Collapsible from 'merchant/components/Collapsible';

export default function MainNavLinkGroup({
  additionalCondition,
  title,
  children,
  ...props
}) {
  return (
    <ShowWhen additionalCondition={additionalCondition}>
      <Collapsible
        title={<Title text={title} />}
        class="NavLinkGroup"
        {...props}
      >
        {children}
      </Collapsible>
    </ShowWhen>
  );
}

function Title({ text }) {
  return <span class="NavLinkGroup--title">{text}</span>;
}
