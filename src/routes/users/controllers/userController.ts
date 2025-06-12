// userController.ts
import { Request, Response } from "express";

export const createUsers = async (req: Request, res: Response) => {
    res.status(200).json({ msg: "Welcome Back" });
};

module.exports = { createUsers };
