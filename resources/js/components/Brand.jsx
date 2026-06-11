import React from 'react';
import { Link } from 'react-router-dom';

export default function Brand({ large = false }) {
    return (
        <Link className={`brand ${large ? 'brand--large' : ''}`} to="/">
            <span>Cine</span><strong>ON</strong>
        </Link>
    );
}
