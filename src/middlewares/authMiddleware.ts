/* eslint-disable @typescript-eslint/no-explicit-any */
import jwt, { JwtPayload } from "jsonwebtoken";
import { Request, Response, NextFunction } from "express";
import { config } from "../config/config";

// Extend Express Request
declare module "express-serve-static-core" {
  interface Request {
    userId?: string | JwtPayload;
  }
}

export const authenticateToken = (req: Request, res: Response, next: NextFunction): void => {
  const authHeader = req.header("Authorization");

  if (!authHeader || !authHeader.startsWith("Bearer ")) {
    res.status(401).json({ status: 401, message: "Unauthorized: Token missing" });
    return;
  }

  const token = authHeader.split(" ")[1];

  if (!config.jwt?.secret) {
    res.status(500).json({ status: 500, message: "JWT secret is not configured" });
    return;
  }

  try {
    const decoded = jwt.verify(token, config.jwt.secret) as JwtPayload;
    req.userId = decoded.userId as string;  // Correct assignment here
    next();
  } catch (error: any) {
    if (error.name === "TokenExpiredError") {
      res.status(401).json({ status: 401, message: "Token expired" });
    } else {
      res.status(403).json({ status: 403, message: "Invalid token" });
    }
  }
};
