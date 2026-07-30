import React from 'react';

export default function PageTransition({ children }) {
  return (
    <div className="slide-up fade-in" style={{ width: '100%', height: '100%', display: 'flex', flexDirection: 'column' }}>
      {children}
    </div>
  );
}
