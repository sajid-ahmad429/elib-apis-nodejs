import { Router, Request, Response } from 'express';

const router = Router();

router.get('/testing', (req: Request, res: Response) => {
  res.status(200).json({
    success: true,
    message: '✅ API is working properly!',
    data: {
      time: new Date().toISOString()
    }
  });
});

export default router;
