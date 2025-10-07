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
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Clock, Calendar, User, AlertTriangle, CheckCircle, Info } from 'lucide-react';
import { TimeSlotPicker, TimeSlot } from '@/components/ui/time-slot-picker';
import { router } from '@inertiajs/react';

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
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [selectedSlot, setSelectedSlot] = useState<TimeSlot | null>(null);

  const { data, setData, post, processing, errors, reset } = useForm({
    lab_id: lab.id,
    start_at: '',
    end_at: '',
  });

  // Generate time slots for the selected date
  const generateTimeSlots = (date: Date, labId: string): TimeSlot[] => {
    const slots: TimeSlot[] = [];
    const startHour = 6; // 6 AM
    const endHour = 22; // 10 PM
    const slotDuration = 4; // 4 hours

    for (let hour = startHour; hour < endHour; hour += slotDuration) {
      const startTime = `${hour.toString().padStart(2, '0')}:00`;
      const endTime = `${(hour + slotDuration).toString().padStart(2, '0')}:00`;

      // Simulate some slots as unavailable or full for demo
      const isAvailable = Math.random() > 0.3; // 70% available
      const currentUsers = isAvailable ? Math.floor(Math.random() * 3) : 0; // 0-2 users
      const maxUsers = 3;

      slots.push({
        id: `${labId}-${date.toDateString()}-${startTime}`,
        startTime,
        endTime,
        available: isAvailable,
        maxUsers,
        currentUsers,
      });
    }

    return slots;
  };

  const timeSlots = generateTimeSlots(selectedDate, lab.id);

  const handleSlotSelect = (slot: TimeSlot) => {
    setSelectedSlot(slot);
    // Convert slot times to datetime-local format
    const slotDate = selectedDate.toISOString().split('T')[0];
    const startDateTime = `${slotDate}T${slot.startTime}:00`;
    const endDateTime = `${slotDate}T${slot.endTime}:00`;

    setData({
      lab_id: lab.id,
      start_at: startDateTime,
      end_at: endDateTime,
    });
  };

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
        // Redirect to lab workspace after successful reservation
        router.visit(`/labs/${lab.id}/workspace`, {
          method: 'get',
          preserveScroll: true,
        });
      },
      onError: (errors) => {
        console.error('Reservation error:', errors);
        // Handle errors if needed
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
          {/* Date Selector */}
          <div className="flex items-center gap-2">
            <Button
              type="button"
              variant={selectedDate.toDateString() === new Date().toDateString() ? 'default' : 'outline'}
              size="sm"
              onClick={() => setSelectedDate(new Date())}
            >
              Today
            </Button>
            <Button
              type="button"
              variant={selectedDate.toDateString() === new Date(Date.now() + 24 * 60 * 60 * 1000).toDateString() ? 'default' : 'outline'}
              size="sm"
              onClick={() => setSelectedDate(new Date(Date.now() + 24 * 60 * 60 * 1000))}
            >
              Tomorrow
            </Button>
          </div>

          <Separator />

          {/* Time Slot Picker */}
          <TimeSlotPicker
            selectedDate={selectedDate}
            slots={timeSlots}
            onSlotSelect={handleSlotSelect}
            selectedSlot={selectedSlot}
            maxSlotsPerUser={3}
            userCurrentSlots={1}
          />

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
                  <li>• 4-hour time slots available</li>
                  <li>• Maximum 3 reservations per day per user</li>
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
            <Button type="submit" disabled={processing || !selectedSlot} className="min-w-32">
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
