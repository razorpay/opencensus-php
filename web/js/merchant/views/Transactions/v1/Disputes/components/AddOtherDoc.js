import React, { useState } from 'react';

const AddOtherDoc = (props) => {
  const { label = 'Others', placeholder = 'Document name', onActionClick } = props;
  const [takeInput, setTakeInput] = useState(false);
  const [input, setInput] = useState('');
  return (
    <div class="others">
      {takeInput ? (
        <>
          <input
            type="text"
            placeholder={placeholder}
            value={input}
            onChange={(e) => setInput(e.target.value)}
            autoFocus
          />
          <button class="btn btn-link add" onClick={() => onActionClick(input)}>
            +Add
          </button>
        </>
      ) : (
        <button class="btn btn-link" onClick={() => setTakeInput(true)}>
          {label}
        </button>
      )}
    </div>
  );
};

export default AddOtherDoc;
