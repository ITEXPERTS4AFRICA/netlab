// src/Components/NetworkBackground.tsx
'use client';

import { useEffect, useRef } from 'react';

import { cn } from '@/lib/utils';

interface NetworkBackgroundProps {
    className?: string;
}

const NetworkBackground = ({ className }: NetworkBackgroundProps) => {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // Couleurs en RGB pour un usage facile dans le canvas
        const colors = {
            node: [241, 202, 19], // Pain grillé aux œufs → nœuds
            line1: [10, 132, 193], // Phuket → lignes principales
            line2: [255, 127, 17], // Glace à l’orange → lignes secondaires (hover/actif)
            bg: [29, 52, 70], // Baleine bleue → fond (plus foncé que #1d3446 pour contraste)
        };

        // Redimensionner le canvas aux dimensions de la fenêtre
        const resizeCanvas = () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        };
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        const particles: { x: number; y: number; vx: number; vy: number; radius: number }[] = [];
        const particleCount = 50;
        const maxDistance = 150;

        // Générer les particules
        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * 0.5,
                vy: (Math.random() - 0.5) * 0.5,
                radius: 1.5 + Math.random() * 2,
            });
        }

        const animate = () => {
            if (!ctx) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Appliquer le fond (Baleine bleue)
            ctx.fillStyle = `rgb(${colors.bg.join(',')})`;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Mettre à jour les positions
            particles.forEach((p) => {
                p.x += p.vx;
                p.y += p.vy;

                // Rebondir sur les bords
                if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

                // Dessiner les nœuds (Pain grillé aux œufs)
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(${colors.node.join(',')}, 0.8)`;
                ctx.fill();
            });

            // Dessiner les lignes entre les nœuds proches
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < maxDistance) {
                        const opacity = 1 - distance / maxDistance;

                        // Alterner les couleurs de ligne selon la distance
                        const useOrange = distance < maxDistance * 0.5;
                        const lineColor = useOrange ? colors.line2 : colors.line1;

                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.strokeStyle = `rgba(${lineColor.join(',')}, ${opacity * 0.4})`;
                        ctx.lineWidth = 0.8;
                        ctx.stroke();
                    }
                }
            }

            requestAnimationFrame(animate);
        };

        animate();

        return () => {
            window.removeEventListener('resize', resizeCanvas);
        };
    }, []);

    return <canvas ref={canvasRef} className={cn('absolute inset-0 h-full w-full', className)} aria-hidden="true" />;
};

export default NetworkBackground;
