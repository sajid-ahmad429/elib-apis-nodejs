import { Router, Request, Response } from "express";
const router = Router();

// ====== Routes ======

// ➕ POST: Add Users
router.post("/register", (req: Request, res: Response) => {
    res.status(200).json({message: "welcome"});
});
export default router;
