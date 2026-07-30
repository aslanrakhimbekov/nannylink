import React from 'react';

export default function Card({ children, onClick, style = {} }) {
  const isClickable = !!onClick;
  const className = `glass fade-in ${isClickable ? 'clickable-card' : ''}`;

  return (
    <div 
      className={className} 
      onClick={onClick}
      style={{
        padding: '20px',
        marginBottom: '16px',
        ...style
      }}
    >
      {children}
    </div>
  );
}
