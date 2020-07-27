import * as React from "react"

function SvgComponent(props) {
  return (
    <svg width={6} height={8} viewBox="0 0 6 8" fill="none" {...props}>
      <path
        d="M3 .8v6.4M.6 4.8L3 7.2l2.4-2.4"
        stroke={props.color}
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  )
}

export default SvgComponent
