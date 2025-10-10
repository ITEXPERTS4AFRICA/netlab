

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { Clock, Users, CheckCircle, X, CalendarDays, Sparkles } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { formatTime, formatTimeDetailed, formatTimeRange, formatDurationFromTimes } from "@/lib/utils";

export interface TimeSlot {
    id: string;
    startTime: string;
    endTime: string;
    available: boolean;
    reservedBy?: string;
    maxUsers: number;
    currentUsers: number;
}

interface TimeSlotPickerProps {
    selectedDate: Date;
    slots: TimeSlot[];
    onSlotSelect: (slot: TimeSlot) => void;
    selectedSlot?: TimeSlot | null;
    maxSlotsPerUser?: number;
    userCurrentSlots?: number;
}

export function TimeSlotPicker({
    selectedDate,
    slots,
    onSlotSelect,
    selectedSlot,
    maxSlotsPerUser = 3,
    userCurrentSlots = 0,
}: TimeSlotPickerProps) {


    const getSlotStatus = (slot: TimeSlot) => {
        if (!slot.available) return "unavailable";
        if (slot.currentUsers >= slot.maxUsers) return "occupied"; // Lab is occupied by another user
        if (userCurrentSlots >= maxSlotsPerUser) return "max_reached";
        return "available";
    };

    const getNextAvailableSlot = () => {
        return slots.find(slot => getSlotStatus(slot) === "available");
    };

    const getRecommendedSlots = () => {
        return slots
            .filter(slot => getSlotStatus(slot) === "available")
            .sort((a, b) => a.currentUsers - b.currentUsers) // Least occupied first
            .slice(0, 3);
    };





    const getSlotIcon = (slot: TimeSlot) => {
        const status = getSlotStatus(slot);
        switch (status) {
            case "available":
                return <CheckCircle className="h-4 w-4" />;
            case "occupied":
                return <Users className="h-4 w-4" />;
            case "unavailable":
                return <X className="h-4 w-4" />;
            case "max_reached":
                return <Clock className="h-4 w-4" />;
            default:
                return null;
        }
    };

    const canSelectSlot = (slot: TimeSlot) => {
        return getSlotStatus(slot) === "available";
    };

    const getAvailabilityColor = (slot: TimeSlot) => {
        const status = getSlotStatus(slot);
        switch (status) {
            case "available":
                return "text-green-700 bg-green-50 border-green-200 hover:bg-green-100";
            case "occupied":
                return "text-orange-700 bg-orange-50 border-orange-200";
            case "unavailable":
                return "text-gray-500 bg-gray-50 border-gray-200";
            case "max_reached":
                return "text-blue-700 bg-blue-50 border-blue-200";
            default:
                return "text-gray-500 bg-gray-50 border-gray-200";
        }
    };

    return (
        <div className="space-y-6">
            {/* Header with modern design */}
            <Card className="border-0 shadow-sm bg-gradient-to-br from-background via-background to-primary/5">
                <CardHeader className="pb-4">
                    <CardTitle className="flex items-center gap-3 text-lg">
                        <motion.div
                            className="p-2 rounded-lg bg-primary/10"
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                        >
                            <CalendarDays className="h-5 w-5 text-primary" />
                        </motion.div>
                        <div className="flex-1">
                            <h3 className="font-semibold flex items-center gap-2">
                                Créneaux disponibles
                                <motion.div
                                    animate={{ rotate: [0, 5, -5, 0] }}
                                    transition={{ duration: 2, repeat: Infinity, repeatDelay: 3 }}
                                >
                                    <Sparkles className="h-4 w-4 text-primary/60" />
                                </motion.div>
                            </h3>
                            <p className="text-sm text-muted-foreground font-normal">
                                {selectedDate.toLocaleDateString('fr-FR', {
                                    weekday: 'long',
                                    day: 'numeric',
                                    month: 'long',
                                    year: 'numeric'
                                })}
                            </p>
                        </div>
                    </CardTitle>

                    {/* Enhanced Legend */}
                    <div className="flex flex-wrap gap-4 pt-2">
                        <motion.div
                            className="flex items-center gap-2 text-xs"
                            initial={{ opacity: 0, x: -20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ delay: 0.1 }}
                        >
                            <div className="w-3 h-3 rounded-full bg-green-100 border border-green-200 shadow-sm"></div>
                            <span className="text-muted-foreground font-medium">Disponible</span>
                        </motion.div>
                        <motion.div
                            className="flex items-center gap-2 text-xs"
                            initial={{ opacity: 0, x: -20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ delay: 0.2 }}
                        >
                            <div className="w-3 h-3 rounded-full bg-orange-100 border border-orange-200 shadow-sm"></div>
                            <span className="text-muted-foreground font-medium">Occupé</span>
                        </motion.div>
                        <motion.div
                            className="flex items-center gap-2 text-xs"
                            initial={{ opacity: 0, x: -20 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ delay: 0.3 }}
                        >
                            <div className="w-3 h-3 rounded-full bg-gray-100 border border-gray-200 shadow-sm"></div>
                            <span className="text-muted-foreground font-medium">Indisponible</span>
                        </motion.div>
                    </div>
                </CardHeader>
            </Card>

            {/* User slot limit info - Modern card design */}
            <Card className="border-l-4 border-l-primary bg-primary/5">
                <CardContent className="p-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Sparkles className="h-4 w-4 text-primary" />
                            <span className="text-sm font-medium">
                                Vos réservations : {userCurrentSlots}/{maxSlotsPerUser}
                            </span>
                        </div>
                        {userCurrentSlots >= maxSlotsPerUser && (
                            <Badge variant="outline" className="text-xs border-orange-200 text-orange-700">
                                Limite atteinte
                            </Badge>
                        )}
                    </div>
                    <div className="mt-2 w-full bg-gray-200 rounded-full h-2">
                        <div
                            className="bg-primary h-2 rounded-full transition-all duration-300"
                            style={{ width: `${(userCurrentSlots / maxSlotsPerUser) * 100}%` }}
                        ></div>
                    </div>
                </CardContent>
            </Card>

            {/* Quick Actions - Smart suggestions */}
            {getRecommendedSlots().length > 0 && !selectedSlot && (
                <Card className="bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200">
                    <CardContent className="p-4">
                        <div className="flex items-center gap-2 mb-3">
                            <Sparkles className="h-4 w-4 text-blue-600" />
                            <h4 className="font-semibold text-blue-800">Créneaux recommandés</h4>
                        </div>
                        <p className="text-sm text-blue-700 mb-3">
                            Voici les créneaux avec le plus de disponibilité :
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {getRecommendedSlots().map((slot) => (
                                <Button
                                    key={slot.id}
                                    variant="outline"
                                    size="sm"
                                    onClick={() => onSlotSelect(slot)}
                                    className="text-blue-700 border-blue-200 hover:bg-blue-100"
                                >
                                    {formatTimeRange(slot.startTime, slot.endTime)}
                                    <Badge variant="secondary" className="ml-2 text-xs">
                                        {slot.maxUsers - slot.currentUsers} places
                                    </Badge>
                                </Button>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Auto-select first available slot if none selected */}
            {!selectedSlot && getNextAvailableSlot() && (
                <div className="text-center">
                    <Button
                        variant="outline"
                        onClick={() => getNextAvailableSlot() && onSlotSelect(getNextAvailableSlot()!)}
                        className="text-primary border-primary/20 hover:bg-primary/5"
                    >
                        <CheckCircle className="h-4 w-4 mr-2" />
                        Sélectionner le premier créneau disponible
                    </Button>
                </div>
            )}

            {/* Time slots grid - Modern card grid */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {slots.map((slot, index) => {
                    const status = getSlotStatus(slot);
                    const isSelected = selectedSlot?.id === slot.id;

                    return (
                        <motion.div
                            key={slot.id}
                            initial={{ opacity: 0, y: 20, scale: 0.9 }}
                            animate={{ opacity: 1, y: 0, scale: 1 }}
                            transition={{
                                delay: index * 0.05,
                                type: "spring",
                                stiffness: 300,
                                damping: 20
                            }}
                            whileHover={{
                                y: -4,
                                scale: 1.02,
                                transition: { duration: 0.2 }
                            }}
                            whileTap={{ scale: 0.98 }}
                            className={`
                                relative group cursor-pointer
                                ${isSelected ? 'ring-2 ring-primary ring-offset-2' : ''}
                            `}
                        >
                            <Card
                                className={`
                                    h-full transition-all duration-300 hover:shadow-xl
                                    ${getAvailabilityColor(slot)}
                                    ${canSelectSlot(slot) ? 'hover:shadow-primary/20 cursor-pointer' : 'opacity-60'}
                                    ${isSelected ? 'shadow-primary/30 shadow-lg' : ''}
                                    backdrop-blur-sm
                                `}
                                onClick={() => canSelectSlot(slot) && onSlotSelect(slot)}
                            >
                                <CardContent className="p-4">
                                    {/* Selection indicator */}
                                    <AnimatePresence>
                                        {isSelected && (
                                            <motion.div
                                                initial={{ scale: 0, opacity: 0 }}
                                                animate={{ scale: 1, opacity: 1 }}
                                                exit={{ scale: 0, opacity: 0 }}
                                                className="absolute -top-2 -right-2 w-6 h-6 bg-primary rounded-full flex items-center justify-center shadow-md"
                                            >
                                                <CheckCircle className="h-3 w-3 text-white" />
                                            </motion.div>
                                        )}
                                    </AnimatePresence>

                                    {/* Header with time and icon */}
                                    <div className="flex items-center justify-between mb-3">
                                        <div className="flex items-center gap-2">
                                            <motion.div
                                                className={`p-1.5 rounded-md ${status === 'available' ? 'bg-green-100 text-green-700' : status === 'occupied' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500'}`}
                                                whileHover={{ scale: 1.1 }}
                                            >
                                                {getSlotIcon(slot)}
                                            </motion.div>
                                            <div>
                                                <div className="font-semibold text-sm">
                                                    {formatTime(slot.startTime)} - {formatTime(slot.endTime)}
                                                </div>
                                                <motion.div
                                                    className="text-xs text-muted-foreground"
                                                    initial={{ opacity: 0 }}
                                                    animate={{ opacity: 1 }}
                                                    transition={{ delay: 0.2 }}
                                                >
                                                    {formatDurationFromTimes(slot.startTime, slot.endTime)}
                                                </motion.div>
                                            </div>
                                        </div>
                                        <AnimatePresence>
                                            {status === "available" && (
                                                <motion.div
                                                    initial={{ scale: 0, opacity: 0 }}
                                                    animate={{ scale: 1, opacity: 1 }}
                                                    exit={{ scale: 0, opacity: 0 }}
                                                >
                                                    <Badge variant="outline" className="text-xs border-green-200 text-green-700">
                                                        Libre
                                                    </Badge>
                                                </motion.div>
                                            )}
                                        </AnimatePresence>
                                    </div>

                                    {/* Availability info */}
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="text-muted-foreground">Capacité:</span>
                                            <span className={`font-medium ${slot.currentUsers >= slot.maxUsers ? 'text-orange-600' : 'text-green-600'}`}>
                                                {slot.maxUsers - slot.currentUsers} places restantes
                                            </span>
                                        </div>

                                        <AnimatePresence>
                                            {slot.currentUsers > 0 && (
                                                <motion.div
                                                    initial={{ opacity: 0, height: 0 }}
                                                    animate={{ opacity: 1, height: "auto" }}
                                                    exit={{ opacity: 0, height: 0 }}
                                                    className="flex items-center justify-between text-xs"
                                                >
                                                    <span className="text-muted-foreground">Occupé:</span>
                                                    <span className="font-medium">
                                                        {slot.currentUsers}/{slot.maxUsers}
                                                    </span>
                                                </motion.div>
                                            )}
                                        </AnimatePresence>
                                    </div>

                                    {/* Progress bar for occupancy */}
                                    <div className="mt-3 w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                        <motion.div
                                            className={`h-1.5 rounded-full transition-all duration-500 ${
                                                slot.currentUsers >= slot.maxUsers ? 'bg-orange-400' : 'bg-green-400'
                                            }`}
                                            initial={{ width: 0 }}
                                            animate={{ width: `${(slot.currentUsers / slot.maxUsers) * 100}%` }}
                                        />
                                    </div>

                                    {/* Overlay for unavailable slots */}
                                    <AnimatePresence>
                                        {!canSelectSlot(slot) && (
                                            <motion.div
                                                initial={{ opacity: 0 }}
                                                animate={{ opacity: 1 }}
                                                exit={{ opacity: 0 }}
                                                className="absolute inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/20"
                                            >
                                                <div className="text-center p-2">
                                                    {status === "max_reached" && (
                                                        <Badge variant="outline" className="text-xs border-blue-200 text-blue-700">
                                                            Limite atteinte
                                                        </Badge>
                                                    )}
                                                    {status === "occupied" && (
                                                        <Badge variant="outline" className="text-xs border-orange-200 text-orange-700">
                                                            Occupé
                                                        </Badge>
                                                    )}
                                                    {status === "unavailable" && (
                                                        <Badge variant="outline" className="text-xs">
                                                            Indisponible
                                                        </Badge>
                                                    )}
                                                </div>
                                            </motion.div>
                                        )}
                                    </AnimatePresence>
                                </CardContent>
                            </Card>
                        </motion.div>
                    );
                })}
            </div>

            {/* Summary section - Modern design */}
            {selectedSlot && (
                <>
                    <Separator />
                    <Card className="bg-primary/5 border-primary/20">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 mb-3">
                                <CheckCircle className="h-5 w-5 text-primary" />
                                <h4 className="font-semibold text-primary">Créneau sélectionné</h4>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div className="space-y-1">
                                    <p className="text-muted-foreground">Horaire</p>
                                    <p className="font-medium font-mono text-base">
                                        {formatTimeDetailed(selectedSlot.startTime)} - {formatTimeDetailed(selectedSlot.endTime)}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground">Durée</p>
                                    <p className="font-medium">
                                        {formatDurationFromTimes(selectedSlot.startTime, selectedSlot.endTime)}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground">Places restantes</p>
                                    <p className="font-medium text-green-600">
                                        {selectedSlot.maxUsers - selectedSlot.currentUsers}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </>
            )}
        </div>
    );
}
