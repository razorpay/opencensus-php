import React from "react";

export default function VerticalBreakup(props) {
  const children = Array.isArray(props.children)
    ? props.children
    : [props.children];
  return (
    <div className="VerticalBreakup">
      {children.map((child, index) => (
        <React.Fragment key={index}>
          <div className="VerticalBreakup--item">{child}</div>
        </React.Fragment>
      ))}
    </div>
  );
}
