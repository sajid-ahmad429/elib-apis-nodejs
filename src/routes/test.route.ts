// src/routes/test.route.ts

import { Router, Request, Response } from 'express';
import { getFormattedDate } from "../utils/dateFormatter"; // ✅ use named import

const router = Router();

/**
 * @route   GET /api/testing
 * @desc    Health check or API working test endpoint
 * @access  Public
 */
router.get('/testing', (req: Request, res: Response) => {
    res.status(200).json({
        success: true,
        statusCode: 200,
        message: '✅ API is working properly!',
        data: {
            timestamp: getFormattedDate(),
        },
    });
});

export default router;
