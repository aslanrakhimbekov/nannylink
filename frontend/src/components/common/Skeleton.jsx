import React from 'react';

export default function Skeleton({ width = '100%', height = '20px', circle = false, style = {} }) {
  return (
    <div 
      className="skeleton" 
      style={{
        width,
        height,
        borderRadius: circle ? '50%' : 'var(--border-radius-sm)',
        marginBottom: '12px',
        ...style
      }}
    />
  );
}
