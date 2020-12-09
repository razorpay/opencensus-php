export function WorkFlow(props) {
  return <ul class="Workflow-list">{props.children}</ul>;
}

export function WorkSection({ heading, children }) {
  return (
    <div class="Workflow-list--item">
      <p>
        <strong>{heading}:</strong>
      </p>
      {children}
    </div>
  );
}
