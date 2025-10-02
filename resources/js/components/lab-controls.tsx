import React, { useState } from 'react';

type Lab = { id?: number | string; cml_id?: string; name?: string };

export default function LabControls({ lab, onMessage }: { lab: Lab; onMessage?: (m: string) => void }) {
    const [loading, setLoading] = useState(false);

    function csrfToken() {
        const m = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
        return m?.content ?? '';
    }

    async function callAction(action: 'start' | 'stop' | 'wipe') {
        if (action === 'wipe' && !confirm('Confirmer le wipe du lab ? Cette action est irréversible.')) return;
        setLoading(true);
        try {
            const resp = await fetch(`/api/labs/${lab.id}/${action}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error((data && (data as any).error) || `Erreur ${action}`);
            const msg = action === 'start' ? 'Lab démarré' : action === 'stop' ? 'Lab arrêté' : 'Lab wiped';
            onMessage?.(msg);
        } catch (e: unknown) {
            const msg = e instanceof Error ? e.message : String(e);
            onMessage?.(`Erreur: ${msg}`);
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="flex gap-2">
            <button className="underline" onClick={() => callAction('start')} disabled={loading}>Démarrer</button>
            <button className="underline" onClick={() => callAction('stop')} disabled={loading}>Arrêter</button>
            <button className="underline text-destructive" onClick={() => callAction('wipe')} disabled={loading}>Wipe</button>
        </div>
    );
}


