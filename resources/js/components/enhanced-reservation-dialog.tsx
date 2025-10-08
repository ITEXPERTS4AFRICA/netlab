import { useState } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Calendar, Clock, Users, CheckCircle, AlertCircle } from "lucide-react";
import { TimeSlotPicker, TimeSlot } from "@/components/ui/time-slot-picker";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { motion } from "framer-motion";
import { router } from "@inertiajs/react";

interface Lab {
    id: string;
    title: string;
    description: string;
    state: string;
    node_count?: number;
}

interface EnhancedReservationDialogProps {
    lab: Lab;
    children: React.ReactNode;
    maxSlotsPerUser?: number;
}

// Generate time slots for the day (6 AM to 10 PM, 4-hour slots)
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

export function EnhancedReservationDialog({
    lab,
    children,
    maxSlotsPerUser = 3
}: EnhancedReservationDialogProps) {
    const [open, setOpen] = useState(false);
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [selectedSlot, setSelectedSlot] = useState<TimeSlot | null>(null);
    const [userCurrentSlots] = useState(1); // This would come from API

    const timeSlots = generateTimeSlots(selectedDate, lab.id);

    const handleSlotSelect = (slot: TimeSlot) => {
        setSelectedSlot(slot);
    };

    const handleReservation = () => {
        if (selectedSlot) {
            // Create reservation data
            const reservationData = {
                lab_id: lab.id,
                start_at: `${selectedDate.toISOString().split('T')[0]}T${selectedSlot.startTime}:00`,
                end_at: `${selectedDate.toISOString().split('T')[0]}T${selectedSlot.endTime}:00`,
            };

            // Make API call to create reservation and redirect to workspace
            router.post('/reservations/custom-create', reservationData, {
                onSuccess: () => {
                    setOpen(false);
                    setSelectedSlot(null);
                    // Redirect to user's reserved labs page after successful reservation
                    router.visit('/labs/my-reserved', {
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
        }
    };

    const formatDate = (date: Date) => {
        return date.toLocaleDateString('fr-FR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {children}
            </DialogTrigger>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                            <Calendar className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 className="text-xl font-semibold">Réserver {lab.title}</h2>
                            <p className="text-sm text-muted-foreground">
                                Choisissez votre créneau de 4 heures
                            </p>
                        </div>
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    {/* Lab Info Card */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-lg">{lab.title}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {lab.description && (
                                <p className="text-sm text-muted-foreground">{lab.description}</p>
                            )}
                            <div className="flex items-center gap-4">
                                <Badge variant={lab.state === 'DEFINED_ON_CORE' ? 'default' : 'secondary'}>
                                    {lab.state}
                                </Badge>
                                {lab.node_count && (
                                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                        <Users className="h-4 w-4" />
                                        <span>{lab.node_count} équipements</span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Date Selector */}
                    <div className="flex items-center gap-2">
                        <Button
                            variant={selectedDate.toDateString() === new Date().toDateString() ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setSelectedDate(new Date())}
                        >
                            Aujourd'hui
                        </Button>
                        <Button
                            variant={selectedDate.toDateString() === new Date(Date.now() + 24 * 60 * 60 * 1000).toDateString() ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setSelectedDate(new Date(Date.now() + 24 * 60 * 60 * 1000))}
                        >
                            Demain
                        </Button>
                    </div>

                    <Separator />

                    {/* Time Slot Picker */}
                    <TimeSlotPicker
                        selectedDate={selectedDate}
                        slots={timeSlots}
                        onSlotSelect={handleSlotSelect}
                        selectedSlot={selectedSlot}
                        maxSlotsPerUser={maxSlotsPerUser}
                        userCurrentSlots={userCurrentSlots}
                    />

                    {/* Reservation Summary & Action */}
                    {selectedSlot && (
                        <>
                            <Separator />
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="space-y-4"
                            >
                                <Card className="border-primary/20 bg-primary/5">
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-primary">
                                            <CheckCircle className="h-5 w-5" />
                                            Confirmation de réservation
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span className="text-muted-foreground">Laboratoire:</span>
                                                <p className="font-medium">{lab.title}</p>
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">Date:</span>
                                                <p className="font-medium">{formatDate(selectedDate)}</p>
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">Créneau:</span>
                                                <p className="font-medium font-mono">
                                                    {selectedSlot.startTime.padStart(5, '0')} - {selectedSlot.endTime.padStart(5, '0')}
                                                </p>
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">Durée:</span>
                                                <p className="font-medium">4 heures</p>
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between pt-4">
                                            <div className="text-sm text-muted-foreground">
                                                Places restantes: {selectedSlot.maxUsers - selectedSlot.currentUsers}
                                            </div>
                                            <Button
                                                onClick={handleReservation}
                                                className="bg-primary hover:bg-primary/90"
                                            >
                                                Confirmer la réservation
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            </motion.div>
                        </>
                    )}

                    {/* Info Section */}
                    <Card className="bg-muted/50">
                        <CardContent className="pt-6">
                            <div className="space-y-2 text-sm text-muted-foreground">
                                <p className="flex items-center gap-2">
                                    <Clock className="h-4 w-4" />
                                    <strong>Créneaux de 4 heures:</strong> Chaque réservation vous donne accès au lab pendant 4 heures consécutives.
                                </p>
                                <p className="flex items-center gap-2">
                                    <Users className="h-4 w-4" />
                                    <strong>Places limitées:</strong> Maximum {maxSlotsPerUser} réservations par jour et par utilisateur.
                                </p>
                                <p className="flex items-center gap-2">
                                    <AlertCircle className="h-4 w-4" />
                                    <strong>Accès automatique:</strong> Le lab sera démarré automatiquement si nécessaire.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </DialogContent>
        </Dialog>
    );
}
