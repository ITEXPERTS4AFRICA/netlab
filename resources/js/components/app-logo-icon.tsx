import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" role="img" aria-label="NetLab logo - architecture réseau">
            <defs>
                <linearGradient id="netGradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#0A74FF" />
                    <stop offset="1" stop-color="#22C3FF" />
                </linearGradient>
            </defs>

            <line x1="60" y1="60" x2="20" y2="20" stroke="url(#netGradient)" stroke-width="4" />
            <line x1="60" y1="60" x2="100" y2="20" stroke="url(#netGradient)" stroke-width="4" />
            <line x1="60" y1="60" x2="20" y2="100" stroke="url(#netGradient)" stroke-width="4" />
            <line x1="60" y1="60" x2="100" y2="100" stroke="url(#netGradient)" stroke-width="4" />

            <circle cx="60" cy="60" r="14" fill="url(#netGradient)" stroke="#0f1724" stroke-width="2" />

            <circle cx="20" cy="20" r="10" fill="#0f1724" stroke="url(#netGradient)" stroke-width="3" />
            <circle cx="100" cy="20" r="10" fill="#0f1724" stroke="url(#netGradient)" stroke-width="3" />
            <circle cx="20" cy="100" r="10" fill="#0f1724" stroke="url(#netGradient)" stroke-width="3" />
            <circle cx="100" cy="100" r="10" fill="#0f1724" stroke="url(#netGradient)" stroke-width="3" />

            <text x="60" y="115" font-family="Montserrat, Inter, sans-serif" font-size="16" font-weight="700" text-anchor="middle" fill="#0f1724">
                NetLab
            </text>
        </svg>
    );
}
