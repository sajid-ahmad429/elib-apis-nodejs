import express, { Request, Response, NextFunction } from "express";
import createHttpError from "http-errors";
import { getFormattedDate } from "../utils/dateFormatter"; // adjust path

const router = express.Router();

router.get('/testing', (req: Request, res: Response, next: NextFunction) => {
    try {
        // Example condition to simulate an error
        const isWorking = false;

        if (!isWorking) {
            // Throw a 503 error if the service is not working
            throw createHttpError(503, "🚫 Service temporarily unavailable.");
        }

        res.status(200).json({
            success: true,
            statusCode: 200,
            message: '✅ API is working properly!',
            data: {
                timestamp: getFormattedDate(),
            },
        });
    } catch (error) {
        next(error); // Forward the error to the error handler middleware
    }
});

export default router;
