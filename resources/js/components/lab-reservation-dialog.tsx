import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Clock, Calendar, User, AlertTriangle, CheckCircle, Info } from 'lucide-react';

interface Lab {
  id: string;
  title?: string;
  description?: string;
  state: string;
}

interface LabReservationDialogProps {
  lab: Lab;
  children: React.ReactNode;
}

export default function LabReservationDialog({ lab, children }: LabReservationDialogProps) {
  const [open, setOpen] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    lab_id: lab.id,
    start_at: '',
    end_at: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!data.start_at || !data.end_at) {
      return;
    }

    const startDateTime = new Date(data.start_at);
    const endDateTime = new Date(data.end_at);

    if (endDateTime <= startDateTime) {
      return;
    }

    post('reservations/custom-create', {
      onSuccess: () => {
        setOpen(false);
        reset();
      },
      preserveScroll: true,
    });
  };

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    if (!isOpen) {
      reset();
    }
  };

  const now = new Date();
  const minTime = now.toISOString().slice(0, 16);
  const maxTimeEnd = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 16); // 7 days from now

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        {children}
      </DialogTrigger>
      <DialogContent className="sm:max-w-[650px] max-h-[90vh] overflow-y-auto">
        <DialogHeader className="space-y-4">
          <div className="flex items-center justify-between">
            <DialogTitle className="flex items-center gap-3 text-xl">
              <div className="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                <Calendar className="h-5 w-5 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <h2 className="font-semibold">Book Lab Reservation</h2>
                <p className="text-sm font-normal text-muted-foreground">Schedule your lab access session</p>
              </div>
            </DialogTitle>
          </div>
        </DialogHeader>

        <Card className="mt-4">
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <h3 className="font-medium flex items-center gap-2">
                <User className="h-4 w-4 text-muted-foreground" />
                {lab.title || `Lab ${lab.id}`}
              </h3>
              <Badge
                variant={lab.state === 'STOPPED' ? 'destructive' : lab.state === 'RUNNING' ? 'default' : 'secondary'}
                className="flex items-center gap-1"
              >
                {lab.state === 'STOPPED' ? (
                  <AlertTriangle className="h-3 w-3" />
                ) : lab.state === 'RUNNING' ? (
                  <CheckCircle className="h-3 w-3" />
                ) : (
                  <Clock className="h-3 w-3" />
                )}
                {lab.state}
              </Badge>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {lab.description && (
              <p className="text-sm text-muted-foreground leading-relaxed">
                {lab.description}
              </p>
            )}
            {lab.state !== 'RUNNING' && (
              <div className="flex items-start gap-2 p-3 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <Info className="h-4 w-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
                <div className="text-sm">
                  <p className="text-amber-800 dark:text-amber-200 font-medium mb-1">Important Note</p>
                  <p className="text-amber-700 dark:text-amber-300">
                    This lab is currently {lab.state.toLowerCase()}. Ensure the lab is running before your reservation starts.
                  </p>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        <Separator />

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="space-y-4">
            <div className="space-y-3">
              <Label htmlFor="start-time" className="text-sm font-medium flex items-center gap-2">
                <Clock className="h-4 w-4" />
                Reservation Start Time
              </Label>
              <Input
                id="start-time"
                type="datetime-local"
                value={data.start_at}
                onChange={(e) => setData('start_at', e.target.value)}
                min={minTime}
                max={maxTimeEnd}
                required
                className="w-full"
              />
              <p className="text-xs text-muted-foreground">
                Choose when you want to start accessing the lab
              </p>
            </div>

            <div className="space-y-3">
              <Label htmlFor="end-time" className="text-sm font-medium flex items-center gap-2">
                <Clock className="h-4 w-4" />
                Reservation End Time
              </Label>
              <Input
                id="end-time"
                type="datetime-local"
                value={data.end_at}
                onChange={(e) => setData('end_at', e.target.value)}
                min={data.start_at || minTime}
                max={maxTimeEnd}
                required
                className="w-full"
              />
              <p className="text-xs text-muted-foreground">
                Select when your reservation should end (up to 7 days from now)
              </p>
            </div>
          </div>

          {Object.keys(errors).length > 0 && (
            <div className="p-3 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
              <div className="text-sm text-red-700 dark:text-red-300">
                {Object.entries(errors).map(([field, message]) => (
                  <p key={field} className="mb-1">{message}</p>
                ))}
              </div>
            </div>
          )}

          <div className="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-md border border-blue-200 dark:border-blue-800">
            <div className="flex items-start gap-2">
              <Info className="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
              <div className="text-sm space-y-1">
                <p className="text-blue-800 dark:text-blue-200 font-medium">Reservation Guidelines</p>
                <ul className="text-blue-700 dark:text-blue-300 space-y-0.5 text-xs">
                  <li>• Maximum reservation duration: 7 days</li>
                  <li>• Ensure no conflicting reservations exist</li>
                  <li>• Lab access requires the lab to be in RUNNING state</li>
                </ul>
              </div>
            </div>
          </div>

          <Separator />

          <div className="flex justify-end gap-3 pt-2">
            <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={processing}>
              Cancel
            </Button>
            <Button type="submit" disabled={processing} className="min-w-32">
              {processing ? (
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
                  Creating Reservation...
                </div>
              ) : (
                <div className="flex items-center gap-2">
                  <CheckCircle className="h-4 w-4" />
                  Book Reservation
                </div>
              )}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
