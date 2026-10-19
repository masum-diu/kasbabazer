import { Download } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useInstallPrompt } from '@/lib/pwa';

/** "Install app" button: uses the browser's install dialog when available, otherwise shows how to install manually. */
export default function InstallButton() {
    const { installed, canPrompt, isIos, install } = useInstallPrompt();
    const [help, setHelp] = useState(false);

    if (installed) {
        return null;
    }

    return (
        <>
            <Button type="button" variant="outline" size="sm" onClick={() => (canPrompt ? install() : setHelp(true))} className="gap-1" aria-label="Install app">
                <Download className="size-4" /> <span className="hidden md:inline">Install app</span>
            </Button>

            <Dialog open={help} onOpenChange={setHelp}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Install Kasba Bazer</DialogTitle>
                        <DialogDescription>Add it to your home screen to open it like an app.</DialogDescription>
                    </DialogHeader>
                    {isIos ? (
                        <ol className="list-decimal space-y-1 pl-5 text-sm">
                            <li>Open this page in <strong>Safari</strong>.</li>
                            <li>Tap the <strong>Share</strong> button.</li>
                            <li>Choose <strong>Add to Home Screen</strong>.</li>
                        </ol>
                    ) : (
                        <ol className="list-decimal space-y-1 pl-5 text-sm">
                            <li>Open the browser menu (<strong>⋮</strong> in Chrome/Edge).</li>
                            <li>Choose <strong>Install app</strong> or <strong>Add to Home screen</strong>.</li>
                            <li>The install option appears only on the live HTTPS site, not in local development.</li>
                        </ol>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
