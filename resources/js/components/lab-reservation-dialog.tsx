import React, { useState } from 'react';
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
import { Card, CardContent } from '@/components/ui/card';
import { Clock, Calendar, User } from 'lucide-react';
import { toast } from 'sonner';

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
  const [startTime, setStartTime] = useState('');
  const [endTime, setEndTime] = useState('');
  const [loading, setLoading] = useState(false);

  const handleReservation = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!startTime || !endTime) {
      toast.message('Please select both start and end times.');
      return;
    }

    const startDateTime = new Date(startTime);
    const endDateTime = new Date(endTime);

    if (endDateTime <= startDateTime) {
      toast.message('End time must be after start time.');
      return;
    }

    setLoading(true);

    try {
      // Send reservation request
      await fetch(`/reservations`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')!.getAttribute('content')!,
        },
        body: JSON.stringify({
          lab_id: lab.id,
          start_at: startTime,
          end_at: endTime,
        }),
      });

      setOpen(false);
      // Refresh the page or update state
      window.location.reload();
    } catch {
      alert('Failed to create reservation. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const now = new Date();
  const minTime = now.toISOString().slice(0, 16);
  const maxTimeEnd = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 16); // 7 days from now

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {children}
      </DialogTrigger>
      <DialogContent className="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Calendar className="h-5 w-5" />
            Book Lab Reservation
          </DialogTitle>
        </DialogHeader>

        <Card>
          <CardContent className="p-4">
            <div className="space-y-3">
              <div className="flex items-center gap-2">
                <User className="h-4 w-4 text-muted-foreground" />
                <span className="font-medium">{lab.title || `Lab ${lab.id}`}</span>
              </div>
              <p className="text-sm text-muted-foreground">
                {lab.description || 'No description available'}
              </p>
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 rounded-full bg-green-500"></div>
                <span className="text-sm">Current Status: {lab.state}</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <form onSubmit={handleReservation} className="space-y-6">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="start-time" className="flex items-center gap-2">
                <Clock className="h-4 w-4" />
                Start Time
              </Label>
              <Input
                id="start-time"
                type="datetime-local"
                value={startTime}
                onChange={(e) => setStartTime(e.target.value)}
                min={minTime}
                max={maxTimeEnd}
                required
                className="w-full"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="end-time" className="flex items-center gap-2">
                <Clock className="h-4 w-4" />
                End Time
              </Label>
              <Input
                id="end-time"
                type="datetime-local"
                value={endTime}
                onChange={(e) => setEndTime(e.target.value)}
                min={startTime || minTime}
                max={maxTimeEnd}
                required
                className="w-full"
              />
            </div>
          </div>

          <div className="text-sm text-muted-foreground">
            <p>• Reservations can be made for up to 7 days in advance</p>
            <p>• Please ensure your reservation does not conflict with existing bookings</p>
            <p>• Lab must be in RUNNING state for access</p>
          </div>

          <div className="flex justify-end gap-3">
            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? 'Creating Reservation...' : 'Book Reservation'}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
