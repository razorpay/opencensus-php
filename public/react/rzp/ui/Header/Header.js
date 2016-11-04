import './Header.styl'

export default function(props) {
  let { title, isLoading, ...attributes } = props
  return (
    <div className='header'>
      <h1 {...attributes}>{title}</h1>
    </div>
  )
}
